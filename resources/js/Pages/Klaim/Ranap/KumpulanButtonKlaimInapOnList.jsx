import React, { useState } from "react";
import { Popover, Row, Col, Button } from "antd";

export default function Index({ nama_pasien, nomer_rm, kode_reg }) {
    return (
        <>
            <Row gutter={[8, 8]} style={{ width: 350 }}>
                <Col span={8}>
                    <Button block size="small">
                        SEP
                    </Button>
                </Col>

                <Col span={8}>
                    <Button block size="small">
                        Resume
                    </Button>
                </Col>

                <Col span={8}>
                    <Button block size="small">
                        Grouping
                    </Button>
                </Col>

                <Col span={8}>
                    <Button block size="small">
                        Klaim
                    </Button>
                </Col>
            </Row>
        </>
    );
}
